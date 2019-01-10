<?php
/**
 * Allows sub classes to have a convenient get_instance method.
 */
class Orbisius_Support_Tickets_Singleton {
    /**
     * Singleton pattern i.e. we have only one instance of this obj
     * @staticvar self $instance
     * @return self
     */
    public static function getInstance() {
        static $instance = null;

        // This will make the calling class to be instantiated.
        // no need each sub class to define this method.
        if (is_null($instance)) {
            $instance = new static();
        }

        return $instance;
    }
}
