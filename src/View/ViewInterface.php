<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\View;

interface ViewInterface
{
    public function set(string $key, $value): ViewInterface;

    public function getContentType(): string;

    public function render(): string;
}