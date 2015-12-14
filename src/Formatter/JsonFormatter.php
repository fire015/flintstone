<?php

/*
 * This file is part of the Flintstone package.
 *
 * (c) Jason M <emailfire@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Flintstone\Formatter;

class JsonFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function encode($data)
    {
        return json_encode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data)
    {
        return json_decode($data, true);
    }
}
