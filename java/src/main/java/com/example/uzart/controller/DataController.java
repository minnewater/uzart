package com.example.uzart.controller;

import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api")
public class DataController {

    /**
     * Ingest raw data from clients.
     * This mirrors the legacy PHP /api endpoint.
     */
    @PostMapping
    public String ingest(@RequestBody String payload) {
        // TODO: persist payload to database
        return "Received: " + payload;
    }
}
