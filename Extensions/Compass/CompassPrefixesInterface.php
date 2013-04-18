<?php

Interface CompassPrefixesInterface
{
    public function supported($prefix);
    public function toPrefix($prefix);
}