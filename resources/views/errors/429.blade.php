@include('errors.layout', [
    'code' => '429',
    'title' => 'アクセスが集中しています',
    'icon' => '🚦',
    'message' => '短時間に多数のリクエストが送信されました。少し時間を置いてから再度お試しください。',
    'showReload' => true,
])
