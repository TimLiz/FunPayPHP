<?php
enum event {
    /**
     * Calls on message, sends messageRepository into listener
     */
    case message;

    /**
     * Calls on payment, sends paymentRepository into listener
     */
    case payment;

    /**
     * Calls on you're messaged, sends messageRepository into listener
     *
     * @deprecated Removed because do not work as planned
     * @removed
     * @since 1.0.4.4
     */
    case youreMessage;

    /**
     * Calls on lot rise
     */
    case lotRise;

    /**
     * Calls every loop interaction
     */
    case loop;
}