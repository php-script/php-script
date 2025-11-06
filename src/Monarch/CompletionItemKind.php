<?php

declare(strict_types=1);

namespace PhpScript\Monarch;

enum CompletionItemKind: int
{
    case Method = 0;
    case Function = 1;
    case Constructor = 2;
    case Field = 3;
    case Variable = 4;
    case ClassName = 5;
    case Struct = 6;
    case Interface = 7;
    case Module = 8;
    case Property = 9;
    case Event = 10;
    case Operator = 11;
    case Unit = 12;
    case Value = 13;
    case Constant = 14;
    case Enum = 15;
    case EnumMember = 16;
    case Keyword = 17;
    case Text = 18;
    case Color = 19;
    case File = 20;
    case Reference = 21;
    case CustomColor = 22;
    case Folder = 23;
    case TypeParameter = 24;
    case User = 25;
    case Issue = 26;
    case Tool = 27;
    case Snippet = 28;
}
