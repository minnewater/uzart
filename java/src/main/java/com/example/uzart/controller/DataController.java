package com.example.uzart.controller;

import com.example.uzart.model.RawPayload;
import com.example.uzart.repository.RawPayloadRepository;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.Objects;

@RestController
@RequestMapping("/api")
public class DataController {

    private final RawPayloadRepository repository;

    public DataController(RawPayloadRepository repository) {
        this.repository = Objects.requireNonNull(repository);
    }

    /**
     * Ingest raw data from clients.
     * This mirrors the legacy PHP /api endpoint.
     */
    @PostMapping
    public String ingest(@RequestBody String payload) {
        RawPayload entity = new RawPayload();
        entity.setPayload(payload);
        repository.save(entity);
        return "Received: " + payload;
    }
}
