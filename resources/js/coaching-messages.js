export function subscribeToCoachingMessages(echo, userId, onMessage) {
    const channelName = `users.${userId}.coaching-messages`;
    const seenMessageIds = new Set();
    let subscribed = true;

    echo.private(channelName).listen('.CoachingMessageReceived', (event) => {
        const messageId = event?.message_id;
        const normalizedId = String(messageId ?? '');

        if (!/^[1-9]\d*$/.test(normalizedId) || seenMessageIds.has(normalizedId)) {
            return;
        }

        seenMessageIds.add(normalizedId);
        onMessage?.(normalizedId);
    });

    return () => {
        if (!subscribed) {
            return;
        }

        subscribed = false;
        echo.leave(channelName);
    };
}
