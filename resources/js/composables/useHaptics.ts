export function useHaptics() {
    function vibrate(pattern: number | number[] = 50) {
        if ('vibrate' in navigator) {
            navigator.vibrate(pattern);
        }
    }

    return {
        ping: () => vibrate(50),
        match: () => vibrate([50, 50, 100]),
        notification: () => vibrate(30),
    };
}
