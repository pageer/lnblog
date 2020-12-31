<?php

class ImageScalingFailed extends Exception
{
    const CODE_BAD_MODE = 10;
    const CODE_BAD_TYPE = 20;
    const CODE_READ_FAILED = 30;
    const CODE_SCALE_FAILED = 40;
    const CODE_SCALE_NOT_NEEDED = 50;
    const CODE_BAD_SIZE = 60;
    const CODE_TARGET_EXISTS = 70;
}
