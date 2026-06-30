"""Reserved keys for ``ClassificationResult.metadata``.

The frozen ``ClassificationResult`` (Phase 3) already exposes an open ``metadata``
bag. We reserve these well-known keys now so provider, version, and timing detail
can ride along — and so the contract never has to change to add them later.

``ClassificationService`` guarantees the four core keys are present on every
result it returns (filling unknowns with ``None``); ``confidence`` is also kept as
a first-class field on the result itself.

The OPTIONAL keys are reserved for later phases (custom model training and
versioning). They are **not** forced onto results yet — providers may populate
them when meaningful — but naming them now keeps the contract stable.
"""

# Core keys — guaranteed present on every ClassificationService result.
MODEL_VERSION = "model_version"
CLASSIFIER_TYPE = "classifier_type"
CONFIDENCE = "confidence"
PREDICTION_TIMESTAMP = "prediction_timestamp"

RESERVED_KEYS = (MODEL_VERSION, CLASSIFIER_TYPE, CONFIDENCE, PREDICTION_TIMESTAMP)

# Optional keys — reserved for trained/versioned models; absent until populated.
CLASSIFIER_NAME = "classifier_name"
CLASSIFIER_VERSION = "classifier_version"
DATASET_VERSION = "dataset_version"

OPTIONAL_KEYS = (CLASSIFIER_NAME, CLASSIFIER_VERSION, DATASET_VERSION)
