<?php

namespace App\Features\Item;

enum ItemType: string {
    case Cassette = 'Cassette';
    case Vinyl = 'Vinyl';
    case Player = 'Player';
}