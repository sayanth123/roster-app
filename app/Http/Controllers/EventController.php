<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Services\RosterParser;

class EventController extends Controller
{

    public function uploadRoster(Request $request, RosterParser $parser)
    {
        $request->validate([
            'roster' => 'required|file|mimes:html,pdf,xls,xlsx,csv,txt,ics'
        ]);

        // Get file path and determine type
        $file = $request->file('roster');
        $filePath = $file->getPathname();
        $extension = $file->getClientOriginalExtension();

        // Map extensions to parser types
        $fileType = match ($extension) {
            'html' => 'html',
            'pdf' => 'pdf',
            'xls', 'xlsx', 'csv' => 'excel',
            'txt' => 'txt',
            'ics' => 'ics',
            default => throw new \Exception("Unsupported file format: $extension")
        };

        // Parse the file
        $parser->parse($filePath, $fileType);

        return response()->json(['message' => 'Roster uploaded successfully']);
    }


    public function getEventsBetweenDates(Request $request)
    {
        $events = Event::whereBetween('date', [$request->start, $request->end])->get();
        return response()->json($events);
    }

    public function getFlightsNextWeek()
    {
        $start = '2022-01-14';
        $end = date('Y-m-d', strtotime($start . ' +7 days'));

        $events = Event::whereBetween('date', [$start, $end])
            ->where('type', 'FLT')
            ->get();

        return response()->json($events);
    }

    public function getStandbyNextWeek()
    {
        $start = '2022-01-14';
        $end = date('Y-m-d', strtotime($start . ' +7 days'));

        $events = Event::whereBetween('date', [$start, $end])
            ->where('type', 'SBY')
            ->get();

        return response()->json($events);
    }

    public function getFlightsFromLocation($location)
    {
        $flights = Event::where('type', 'FLT')
            ->where('departure', $location)
            ->get();
    
        return response()->json($flights);
    }
}

