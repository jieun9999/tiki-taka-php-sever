<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결

$userId = $_POST['userId'];
$message = $_POST['message'];

error_log("data: " . $userId . $message);

//password 필드가 현재 null이거나 비어 있는 상태라 하더라도, UPDATE 쿼리를 사용하여 그 필드를 새로운 값으로 업데이트하는 것은 완전히 가능
// UPDATE 쿼리는 지정된 조건에 맞는 레코드를 찾아 해당 레코드의 하나 또는 여러 필드의 값을 변경
// UPDATE 쿼리 준비
$sql = "UPDATE userProfile SET profile_message = :message WHERE user_id = :userId";
$stmt = $conn-> prepare($sql);

// 파라미터 바인딩
$stmt->bindParam(':message', $message, PDO::PARAM_STR);
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

// 쿼리 실행
$result = $stmt->execute();

// 결과 확인 및 출력
if ($result) {

    // rowCount()를 사용하여 업데이트된 행의 수 확인
    if ($stmt->rowCount() > 0) {
    echo json_encode(["success" => true, "message" => "저장 성공!"]);
    }

    else{
    echo json_encode(["success" => true, "message" => "업데이트 될 행이 존재하지 않습니다"]);
    }

} else {
    echo json_encode(["success" => false, "message" => "저장 실패"]);
}


?>