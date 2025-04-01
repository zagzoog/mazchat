<?php

interface PluginInterface {
    /**
     * Get the hooks that this plugin implements
     * @return array An array of hook names and their enabled status
     */
    public function getHooks();
    
    /**
     * Execute a specific hook
     * @param string $hookName The name of the hook to execute
     * @param array $args Arguments to pass to the hook
     * @return mixed The result of the hook execution
     */
    public function executeHook($hookName, $args = []);
} 