<?php
namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use App\Models\Event;
use Maatwebsite\Excel\Facades\Excel;
use Smalot\PdfParser\Parser;
use Sabre\VObject\Reader;
use Illuminate\Support\Facades\Storage;

class RosterParser
{
    public function parse(string $filePath, string $fileType)
    {
        $events = [];

        switch ($fileType) {
            case 'html':
                $htmlContent = file_get_contents($filePath);
                $events = $this->parseHtml($htmlContent);
                break;

            case 'pdf':
                $events = $this->parsePdf($filePath);
                break;

            case 'excel':
                $events = $this->parseExcel($filePath);
                break;

            case 'txt':
                $events = $this->parseTxt($filePath);
                break;

            case 'ics':
                $events = $this->parseWebCal($filePath);
                break;

            default:
                throw new \Exception("Unsupported file format: $fileType");
        }

        return $events;
    }


    private function parseHtml(string $htmlContent)
    {
        $crawler = new Crawler($htmlContent);
        $events = [];
        $lastKnownDate = null;

        $crawler->filter('table.activityTableStyle tbody tr')->each(function (Crawler $row, $index) use (&$events, &$lastKnownDate) {
            if ($index === 0) return;

            $rawDate = trim($row->filter('.activitytablerow-date')->text(''));
            if (!empty($rawDate)) {
                $lastKnownDate = $this->parseDate($rawDate);
            }
            $date = $lastKnownDate;

            $activity = trim($row->filter('.activitytablerow-activity')->text(''));
            $departure = trim($row->filter('.activitytablerow-fromstn')->text(''));
            $arrival = trim($row->filter('.activitytablerow-tostn')->text(''));

            $std_utc = $this->cleanTime(trim($row->filter('.activitytablerow-stdutc')->text('')), $date);
            $sta_utc = $this->cleanTime(trim($row->filter('.activitytablerow-stautc')->text('')), $date);
            $check_in_utc = $this->cleanTime(trim($row->filter('.activitytablerow-checkinutc')->text('')), $date);
            $check_out_utc = $this->cleanTime(trim($row->filter('.activitytablerow-checkoututc')->text('')), $date);

            $type = $this->detectEventType($activity, $check_in_utc, $check_out_utc);

            $events[] = Event::create([
                'date' => $date,
                'type' => $type,
                'flight_number' => $type === 'FLT' ? $activity : null,
                'departure' => $departure !== 'From' ? $departure : null,
                'arrival' => $arrival !== 'To' ? $arrival : null,
                'std_utc' => $std_utc,
                'sta_utc' => $sta_utc,
                'check_in_utc' => $check_in_utc,
                'check_out_utc' => $check_out_utc,
            ]);
        });
        return $events;
    }


    private function parsePdf(string $filePath)
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        return $this->parseText($text);
    }

    
    private function parseExcel(string $filePath)
    {
        return Excel::toArray([], $filePath)[0]; // Converts Excel to array
    }


    private function parseTxt(string $filePath)
    {
        $text = file_get_contents($filePath);
        return $this->parseText($text);
    }


    private function parseWebCal(string $filePath)
    {
        $calendar = Reader::read(file_get_contents($filePath));
        $events = [];

        foreach ($calendar->VEVENT as $event) {
            $date = date('Y-m-d', strtotime($event->DTSTART));
            $activity = (string) $event->SUMMARY;
            $std_utc = date('H:i', strtotime($event->DTSTART));
            $sta_utc = date('H:i', strtotime($event->DTEND));
            $location = (string) $event->LOCATION;

            $departure = $location ?: null;
            $arrival = null;

            $type = $this->detectEventType($activity, $std_utc, $sta_utc);

            $events[] = Event::create([
                'date' => $date,
                'type' => $type,
                'flight_number' => $type === 'FLT' ? $activity : null,
                'departure' => $departure,
                'arrival' => $arrival,
                'std_utc' => $std_utc,
                'sta_utc' => $sta_utc,
                'check_in_utc' => null,
                'check_out_utc' => null,
            ]);
        }

        return $events;
    }

    private function parseText(string $text)
    {
        $events = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($text)); // Properly split lines
        $lastKnownDate = null;
    
        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line)); // Normalize spaces
    
            if (preg_match('/([A-Za-z]{3})\s+(\d{1,2})/', $line, $matches)) {
                $lastKnownDate = $this->parseDate($matches[0]); 
            }
    
            preg_match('/\b([A-Z]{2}\d{2,4})\b/', $line, $flightMatches);
            $activity = $flightMatches[1] ?? null;
    
            preg_match_all('/([A-Z]{3})\s+(\d{4})/', $line, $airportMatches, PREG_SET_ORDER);
    
            $departure = $arrival = $std_utc = $sta_utc = null;
            if (isset($airportMatches[0])) { 
                $departure = $airportMatches[0][1];
                $std_utc = $this->formatTime($airportMatches[0][2], $lastKnownDate);
            }
            if (isset($airportMatches[1])) { 
                $arrival = $airportMatches[1][1];
                $sta_utc = $this->formatTime($airportMatches[1][2], $lastKnownDate);
            }
    
            $check_in_utc = null;
            if (preg_match('/C\/I\(Z\):\s*(\d{4})/', $line, $checkInMatches)) {
                $check_in_utc = $this->formatTime($checkInMatches[1], $lastKnownDate);
            }
    
            $check_out_utc = null;
            if (preg_match('/C\/O\(Z\):\s*(\d{4})/', $line, $checkOutMatches)) {
                $check_out_utc = $this->formatTime($checkOutMatches[1], $lastKnownDate);
            }
    
            $type = $this->detectEventType($activity, $check_in_utc, $check_out_utc);
    
            if (!$lastKnownDate) {
                continue; // Skip if no valid date
            }
    
            if (!$activity && !$departure && !$arrival && !$check_in_utc && !$check_out_utc) {
                continue;
            }
    
            $events[] = [
                'date' => $lastKnownDate,
                'type' => $type,
                'flight_number' => $type === 'FLT' ? $activity : null,
                'departure' => $departure,
                'arrival' => $arrival,
                'std_utc' => $std_utc,
                'sta_utc' => $sta_utc,
                'check_in_utc' => $check_in_utc,
                'check_out_utc' => $check_out_utc,
            ];
        }
        return $events;
    }
    
    private function parseDate($date)
    {
        $date = trim(str_replace(["\u{A0}", "\xc2\xa0"], '', $date));
        preg_match('/\d+/', $date, $matches);
        $day = $matches[0] ?? null;

        if (!$day) return null;

        return date('Y-m-d', strtotime("2022-01-$day"));
    }

    private function cleanTime($time, &$date)
    {
        $time = str_replace(["\u{A0}", "\xc2\xa0"], '', $time);

        if (str_contains($time, '-1')) {
            $time = str_replace('-1', '', $time);
            $date = date('Y-m-d', strtotime($date . ' -1 day'));
        }

        if (preg_match('/^\d{4}$/', $time)) {
            return substr($time, 0, 2) . ':' . substr($time, 2, 2);
        }

        return null;
    }

    private function formatTime($time, &$date = null)
    {
        $time = str_replace(["\u{A0}", "\xc2\xa0"], '', $time);

        // If the time contains "-1", adjust the date
        if (str_contains($time, '-1')) {
            $time = str_replace('-1', '', $time);
            if ($date) {
                $date = date('Y-m-d', strtotime($date . ' -1 day')); // Adjust date by -1 day
            }
        }

        // Convert HHMM to HH:MM (e.g., "2300" → "23:00")
        if (preg_match('/^\d{4}$/', $time)) {
            return substr($time, 0, 2) . ':' . substr($time, 2, 2);
        }

        return null; 
    }

    private function detectEventType($activity, $check_in_utc, $check_out_utc)
    {
        if (preg_match('/^[A-Z]{2}\d+$/', $activity)) {
            return 'FLT'; // Flight
        } elseif (str_contains($activity, 'OFF')) {
            return 'DO'; // Day Off
        } elseif (str_contains($activity, 'SBY')) {
            return 'SBY'; // Standby
        } elseif (!empty($check_in_utc)) {
            return 'CI'; // Check-In
        } elseif (!empty($check_out_utc)) {
            return 'CO'; // Check-Out
        } else {
            return 'UNK'; // Unknown event
        }
    }

}
