<?php
declare(strict_types=1);

const FV7_ROOT = __DIR__ . '/..';
const FV7_PUBLIC = FV7_ROOT . '/public';
const FV7_STORAGE = FV7_ROOT . '/storage';
const FV7_DB = FV7_STORAGE . '/fikrimvar.sqlite';
const FV7_UPLOAD_ROOT = FV7_PUBLIC . '/uploads';
const FV7_ADMIN_LOCAL_ONLY = true;
const FV7_IMAGE_MAX_BYTES = 20 * 1024 * 1024;
const FV7_VIDEO_MAX_BYTES = 250 * 1024 * 1024;
const FV7_OTHER_MAX_BYTES = 40 * 1024 * 1024;
const FV7_SESSION_NAME = 'fikrimvar_v7_admin';
