<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

// JSON 데이터를 PHP에서 받기
$json = file_get_contents('php://input');
$data = json_decode($json);

$userId = $data->userId;
$folderId = isset($data->folderId) ? $data->folderId : null; 
$text = $data->text;
$title = isset($data->title) ? $data->title : null;
$location = isset($data->location) ? $data->location : null;
$partnerId = isset($data->partnerId) ? $data->partnerId : null;

try{
    //트랜잭션 시작
    $conn -> beginTransaction();

    // folderId가 주어지지 않은 경우에만 새로운 폴더를 생성
    if(empty($folderId)){
        $sqlFolder = "INSERT INTO storyFolder (user_id, data_type) VALUES (:userId, :dataType)";
        $stmtFolder = $conn->prepare($sqlFolder);
        $stmtFolder->execute([':userId' => $userId, ':dataType' => "text"]);
        $folderId = $conn->lastInsertId();
    
    }

    // 스토리 카드 INSERT 쿼리 준비 및 실행
    $sqlCard = "INSERT INTO storyCard (folder_id, user_id, memo, data_type) VALUES (:folderId, :userId, :memo, :dataType)";
    $stmtCard = $conn->prepare($sqlCard);
    $stmtCard->execute([':folderId' => $folderId, ':userId' => $userId, ':memo' => $text, ':dataType' => "text"]);
    $cardId = $conn -> lastInsertId();

    // folderId가 새로 생성된 경우에만 title과 location을 업데이트
    if(!empty($text) && !empty($folderId) && empty($data->folderId)){
        $sqlUpdateFolder = "UPDATE storyFolder SET title = :title, location = :location
                             WHERE folder_id = :folderId";
        $stmtUpdateFolder = $conn->prepare($sqlUpdateFolder);
        $stmtUpdateFolder->execute([':folderId' => $folderId, ':title' => $title, ':location' => $location]);
    }
    
    // 모든 쿼리가 성공적으로 실행되면, 트랜잭션 커밋
    $conn->commit();


    // 알림 데이터 구성
    require_once '../FCM/selectFcmTokenAndProfileImg.php';
        $result = selectFcmTokenAndProfileImg($conn, $partnerId, $userId);
        $tokenRow = $result['fcmToken'];
        $token = $tokenRow['token'];
        $profileInfo = $result['profileInfo'];
        $userImg = $profileInfo['profile_image'];
        $name = $profileInfo['name'];
        $messageData = [
            'flag' => 'story_memo_notification',
            'title' => 'tiki taka',
            'body' => $name.'님이 메모를 추가했습니다. 확인해보세요!',
            'userProfile' => $userImg,
            'cardId' => $cardId
            ];
        
    // FCM 서버에 알림 데이터를 보내기
    // require_once : 다른 파일을 현재 스크립트에 포함시킬 때 사용
    require_once '../FCM/sendFcmNotification.php';
    $resultFCM = sendFcmNotification($token, $messageData);
    
    if($resultFCM){
        echo json_encode(["success" => true, "message" => "게시 성공!"]);
    }else{
        echo json_encode(["success" => false, "message" => "게시 실패 ㅠ: sendFcmNotification() 실행시 문제가 생김"]);
    }

}catch(PDOException $e) {
    // 오류 발생 시 트랜잭션 롤백
    $conn->rollback();
    // 실패 응답
    echo json_encode(["success" => false, "message" => "게시 실패 ㅠ: " . $e->getMessage()]);
}

?>