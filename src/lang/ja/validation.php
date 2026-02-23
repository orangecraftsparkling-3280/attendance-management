<?php

return [
    'required' => ':attributeは必須です。',
    'email' => ':attributeは有効なメールアドレス形式で指定してください。',
    'max' => [
        'string' => ':attributeは:max文字以内で指定してください。',
    ],
    'min' => [
        'string' => ':attributeは:min文字以上で指定してください。',
    ],
    'confirmed' => ':attributeが一致しません。',
    'unique' => '指定された:attributeは既に使用されています。',
    'attributes' => [
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'name' => '名前',
    ],
];
