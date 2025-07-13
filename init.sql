
CREATE UNLOGGED TABLE payments (
    correlationId UUID PRIMARY KEY,
    amount DECIMAL NOT NULL,
    processor VARCHAR(20) NOT NULL,
    requested_at TIMESTAMP NOT NULL
);

CREATE INDEX payments_requested_at ON payments (requested_at);
