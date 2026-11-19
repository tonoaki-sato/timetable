@include('errors.layout', [
    'code' => '503',
    'title' => 'ただいまメンテナンス中です',
    'icon' => '🛠️',
    'message' => '現在システムメンテナンスを実施しています。完了までしばらくお待ちください。',
    'showReload' => true,
])
