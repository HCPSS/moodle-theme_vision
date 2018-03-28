<?php

/**
 * @file
 * Contains theme_vision_overridden_renderer_factory
 */

/**
 * A class that finds renderers.
 */
class theme_vision_overridden_renderer_factory extends theme_overridden_renderer_factory {

    /*
     * An array where the key is a composite of of component subtype and target
     * and the value is the resolved class name.
     *
     * @var array
     */
    static protected $cache = array();

    /**
     * {@inheritdoc}
     *
     * Cache class names so that we don't hit the autoloader(s) over and over.
     *
     * @see theme_overridden_renderer_factory
     */
    public function get_renderer(moodle_page $page, $component, $subtype = null, $target = null) {
        $key = vsprintf('%s:%s:%s', [
            $component,
            $subtype ?: 'null',
            $target ?: 'null',
        ]);

        if (isset(self::$cache[$key])) {
            $renderer = new self::$cache[$key]($page, $target);
        } else {
            // This is the expensive operation we are trying to avoid.
            $renderer = parent::get_renderer($page, $component, $subtype, $target);
            self::$cache[$key] = get_class($renderer);
        }

        return $renderer;
    }
}
