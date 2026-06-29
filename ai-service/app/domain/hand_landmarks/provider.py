"""Domain port for the hand-landmark capability.

The domain depends on this abstraction, not on any specific library. MediaPipe
is one implementation (in the infrastructure layer); it can be replaced without
touching the domain.
"""

from abc import ABC, abstractmethod


class HandLandmarkProvider(ABC):
    """Lifecycle contract for a hand-landmark engine.

    Phase 1 covers only the lifecycle (load / warmup / readiness / shutdown).
    Landmark extraction (`detect`) is added in a later phase.
    """

    @abstractmethod
    def load(self) -> None:
        """Load the underlying model into memory."""

    @abstractmethod
    def warmup(self) -> None:
        """Run a no-op inference so the first real request is fast."""

    @abstractmethod
    def is_ready(self) -> bool:
        """Whether the provider is initialized and able to serve requests."""

    @abstractmethod
    def close(self) -> None:
        """Release the underlying model/resources."""

    @property
    @abstractmethod
    def provider_version(self) -> str:
        """Version string of the underlying engine (for diagnostics)."""
