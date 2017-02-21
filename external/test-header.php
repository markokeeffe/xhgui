<?php

tideways_enable(TIDEWAYS_FLAGS_CPU | TIDEWAYS_FLAGS_MEMORY | TIDEWAYS_FLAGS_NO_SPANS);

register_shutdown_function(
    function () {

        $profile = tideways_disable();


        // ignore_user_abort(true) allows your PHP script to continue executing, even if the user has terminated their request.
        // Further Reading: http://blog.preinheimer.com/index.php?/archives/248-When-does-a-user-abort.html
        // flush() asks PHP to send any data remaining in the output buffers. This is normally done when the script completes, but
        // since we're delaying that a bit by dealing with the xhprof stuff, we'll do it now to avoid making the user wait.
        ignore_user_abort(true);
        flush();

        $host = array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : null;
        $uri = array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : null;
        $path = realpath(dirname(__FILE__) . '/../output') . '/' . date('Y-m-d') . '.log';

        if (empty($uri) && isset($_SERVER['argv'])) {
            $cmd = basename($_SERVER['argv'][0]);
            $uri = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1));
        }

        if ($host !== 'www.digistorm.com.au' && $uri !== '/company') {
            return;
        }

        $time = array_key_exists('REQUEST_TIME', $_SERVER)
            ? $_SERVER['REQUEST_TIME']
            : time();

        $data = [
            'host' => $host,
            'uri' => $uri,
            'timestamp' => $time,
            'wall_time' => $profile['main()']['wt'],
            'cpu' => $profile['main()']['cpu'],
            'memory_usage' => $profile['main()']['mu'],
            'peak_memory_usage' => $profile['main()']['pmu'],
        ];

        error_log(json_encode($data) . PHP_EOL, 3, $path);
    }
);