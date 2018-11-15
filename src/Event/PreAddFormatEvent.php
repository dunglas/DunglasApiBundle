<?php


namespace ApiPlatform\Core\Event;


use Symfony\Component\EventDispatcher\Event;

class PreAddFormatEvent extends Event
{
    const NAME = ApiPlatformEvents::PRE_ADD_FORMAT;
    private $formats;

    public function __construct($formats)
    {
        $this->formats = $formats;
    }

    public function getFormats()
    {
        return $this->formats;
    }

    public function setFormats($formats): void
    {
        $this->formats = $formats;
    }
}
