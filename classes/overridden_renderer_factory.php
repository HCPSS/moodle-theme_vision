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
            return new self::$cache[$key]($page, $component, $subtype, $target);
        }

        $renderer = parent::get_renderer($page, $component, $subtype, $target);
        self::$cache[$key] = get_class($renderer);

        return $renderer;
    }
}
