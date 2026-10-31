<?php

namespace App\Enum;

enum SortField: string
{
    case Added = 'added';
    case Name = 'name';
    case Year = 'year';
    case Rating = 'rating';
    case Length = 'length';
}
