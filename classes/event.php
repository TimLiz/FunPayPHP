<?php
abstract class event {
    /**
     * Calls on message, sends messageRepository into listener
     */
    const message = 1;

    /**
     * Calls on payment, sends paymentRepository into listener
     */
    const payment = 2;

    /**
     * Calls on you're messaged, sends messageRepository into listener
     *
     * @deprecated Removed because do not work as planned
     * @removed
     * @since 1.0.4.4
     */
    const youreMessage = 3;

    /**
     * Calls on lot rise
     */
    const lotRise = 4;

    /**
     * Calls every loop interaction
     */
    const loop = 5;
}