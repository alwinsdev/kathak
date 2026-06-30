"""Classification-domain exceptions.

These are pure domain errors (no HTTP status). When classification is wired to an
HTTP endpoint in a later phase, the API layer will map them to responses.
"""


class ClassificationError(Exception):
    """Base class for classification errors."""


class InvalidFeaturesError(ClassificationError, ValueError):
    """The feature set is missing, empty, or incomplete, so it cannot be
    classified. Also raised for null/empty landmark input upstream of features."""
