<?php

namespace AuthSystem\Core\Middleware;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;


interface MiddlewareInterface
{

    public function handle(Request $request, callable $next): Response;
}