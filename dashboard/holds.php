$stmt = $pdo->prepare("
    SELECT *
    FROM Holds
    WHERE User_ID = :user_id
");

$stmt->execute([':user_id' => $userID]);
$holds = $stmt->fetchAll(PDO::FETCH_ASSOC);
