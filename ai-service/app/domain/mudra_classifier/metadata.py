"""Reserved keys for ``ClassificationResult.metadata``.

The frozen ``ClassificationResult`` (Phase 3) already exposes an open ``metadata``
bag. We reserve these well-known keys now so provider, version, and timing detail
can ride along — and so the contract never has to change to add them later.

``ClassificationService`` guarantees all four keys are present on every result it
returns (filling unknowns with ``None``); ``confidence`` is also kept as a
first-class field on the result itself.
"""

MODEL_VERSION = "model_version"
CLASSIFIER_TYPE = "classifier_type"
CONFIDENCE = "confidence"
PREDICTION_TIMESTAMP = "prediction_timestamp"

RESERVED_KEYS = (MODEL_VERSION, CLASSIFIER_TYPE, CONFIDENCE, PREDICTION_TIMESTAMP)
