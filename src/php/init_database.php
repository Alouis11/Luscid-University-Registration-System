<?php
    require_once '../../db.php';

    try {
        // 2. Read the SQL file contents
        $sqlFile = '../mysql/init_database.sql';
        $sql = file_get_contents($sqlFile);

        if ($sql === false) {
            throw new Exception("Database Schema Creation Failed - Failed to read init_database.sql");
        }

        // 3. Execute the entire SQL script
        $pdo->exec($sql);
        echo "Database successfully initialized.";

    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    } catch (Exception $e) {
        echo "General Error: " . $e->getMessage();
    }
?>