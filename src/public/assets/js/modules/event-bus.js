/**
 * Event Bus Module
 * Implements a publish/subscribe pattern for communication between modules
 */

const EventBus = (function() {
    'use strict';
    
    // Store for event subscriptions
    const events = {};
    
    /**
     * Subscribe to an event
     * @public
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     * @returns {Object} - Subscription object with unsubscribe method
     */
    function subscribe(event, callback) {
        if (!events[event]) {
            events[event] = [];
        }
        
        const index = events[event].push(callback) - 1;
        
        // Return subscription handle with unsubscribe method
        return {
            unsubscribe: function() {
                events[event].splice(index, 1);
                
                // Clean up event array if empty
                if (events[event].length === 0) {
                    delete events[event];
                }
            }
        };
    }
    
    /**
     * Publish an event with data
     * @public
     * @param {string} event - Event name
     * @param {*} data - Event data
     */
    function publish(event, data) {
        if (!events[event]) {
            return;
        }
        
        // Copy the subscribers array to avoid issues if unsubscribe is called during publish
        const subscribers = [...events[event]];
        
        subscribers.forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                console.error(`Error in event listener for ${event}:`, error);
            }
        });
    }
    
    /**
     * Clear all subscriptions for an event or all events
     * @public
     * @param {string} [event] - Event name, if omitted clears all events
     */
    function clear(event) {
        if (event) {
            delete events[event];
        } else {
            // Clear all events
            Object.keys(events).forEach(key => {
                delete events[key];
            });
        }
    }
    
    /**
     * Check if an event has subscribers
     * @public
     * @param {string} event - Event name
     * @returns {boolean} - True if the event has subscribers
     */
    function hasSubscribers(event) {
        return !!events[event] && events[event].length > 0;
    }
    
    /**
     * Get the count of subscribers for an event
     * @public
     * @param {string} event - Event name
     * @returns {number} - Number of subscribers
     */
    function subscriberCount(event) {
        return events[event] ? events[event].length : 0;
    }
    
    /**
     * One-time subscription that automatically unsubscribes after first event
     * @public
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     * @returns {Object} - Subscription object with unsubscribe method
     */
    function once(event, callback) {
        const subscription = subscribe(event, function onceHandler(data) {
            subscription.unsubscribe();
            callback(data);
        });
        
        return subscription;
    }
    
    // Public API
    return {
        subscribe,
        publish,
        clear,
        once,
        hasSubscribers,
        subscriberCount
    };
})();

// Export module
window.EventBus = EventBus;