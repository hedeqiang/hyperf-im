<?php

declare(strict_types=1);

/*
 * This file is part of the hedeqiang/im.
 *
 * (c) hedeqiang<laravel_code@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Hedeqiang\IM;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
            ],
            'commands' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config of im.',
                    'source' => __DIR__.'/../publish/im.php',
                    'destination' => BASE_PATH.'/config/autoload/im.php',
                ],
            ],
        ];
    }
}
