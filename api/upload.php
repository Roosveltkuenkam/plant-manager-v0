<?php
// api/upload.php
header('Content-Type: application/json; charset=utf-8');
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);


if (!isset($_FILES['image'])) {
echo json_encode(['error'=>'no_file']); exit;
}
$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
echo json_encode(['error'=>'upload_error','code'=>$file['error']]); exit;
}
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$allowed = ['jpg','jpeg','png','gif'];
if (!in_array(strtolower($ext), $allowed)) {
echo json_encode(['error'=>'invalid_ext']); exit;
}
$target = $uploadDir . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $target)) {
echo json_encode(['error'=>'move_failed']); exit;
}
$publicPath = 'uploads/' . basename($target);
echo json_encode(['ok'=>true, 'path'=>$publicPath]);