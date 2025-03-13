## 📌 Database Information

- The **main SQLite database** and the **test database** are located inside the `database/` folder.
- The following files are included:
  - `database/database.sqlite` → Main database for local development
  - `database/database_test.sqlite` → Database used for running tests

### 🔹 How to Use the Database?
1. Ensure your `.env` file is configured to use SQLite:
   ```ini
   DB_CONNECTION=sqlite
   DB_DATABASE=/absolute/path/to/database.sqlite


   ![image](https://github.com/user-attachments/assets/40fc3a51-1dab-498d-a10b-667a29fbe9db)
