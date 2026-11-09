import test from 'node:test';
import assert from 'node:assert/strict';
import { subscribeToCoachingMessages } from '../../resources/js/coaching-messages.js';

function fakeEcho() {
    const handlers = new Map();
    const leftChannels = [];

    return {
        handlers,
        leftChannels,
        private(channelName) {
            return {
                listen(eventName, callback) {
                    handlers.set(`${channelName}:${eventName}`, callback);
                    return this;
                },
            };
        },
        leave(channelName) {
            leftChannels.push(channelName);
        },
    };
}

test('increments only once for each valid message id and leaves the private channel on cleanup', () => {
    const echo = fakeEcho();
    const receivedIds = [];
    const cleanup = subscribeToCoachingMessages(echo, 42, (messageId) => receivedIds.push(messageId));
    const eventHandler = echo.handlers.get('users.42.coaching-messages:.CoachingMessageReceived');

    eventHandler({ message_id: 1001 });
    eventHandler({ message_id: 1001, body: 'must not be consumed' });
    eventHandler({ message_id: 1002 });
    eventHandler({ message_id: null });

    assert.deepEqual(receivedIds, ['1001', '1002']);

    cleanup();
    cleanup();

    assert.deepEqual(echo.leftChannels, ['users.42.coaching-messages']);
});
