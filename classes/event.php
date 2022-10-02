<?php
abstract class event {
    /**
     * Calls on message, sends messageRepository into listener
     */
    const message = 1;

    /**
     * Calls on payment, sends PaymentRepository into listener
     */
    const payment = 2;

    /**
     * Calls on you're messaged, sends messageRepository into listener
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