<?php
class EventDispatcher {

    private static array $_subscribers = [];

    public static function subscribe(string $eventName, callable $handler): void {
        self::$_subscribers[$eventName][] = $handler;
    }

    public static function publish(string $eventName, array $payload = []): void {
        foreach (self::$_subscribers[$eventName] ?? [] as $handler) {
            try {
                call_user_func($handler, $payload);
            } catch (Exception $e) {
                error_log('[EventDispatcher] Erreur listener "' . $eventName . '": ' . $e->getMessage());
            }
        }
    }

    public static function getSubscribers(string $eventName): array {
        return self::$_subscribers[$eventName] ?? [];
    }

    public static function reset(): void {
        self::$_subscribers = [];
    }
}
?>
