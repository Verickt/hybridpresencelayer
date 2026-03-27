<?php

namespace App\Enums;

enum ParticipantStatus: string
{
    case Available = 'available';
    case InSession = 'in_session';
    case AtBooth = 'at_booth';
    case Busy = 'busy';
    case Away = 'away';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::InSession => 'In Session',
            self::AtBooth => 'At Booth',
            self::Busy => 'Busy',
            self::Away => 'Away',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::InSession => 'purple',
            self::AtBooth => 'blue',
            self::Busy => 'red',
            self::Away => 'gray',
        };
    }
}
