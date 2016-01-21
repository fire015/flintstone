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

interface FormatterInterface
{
    /**
     * Encode data into a string.
     *
     * @param mixed $data
     *
     * @return string
     */
    public function encode($data);

    /**
     * Decode a string into data.
     *
     * @param string $data
     *
     * @return mixed
     */
    public function decode($data);
}
