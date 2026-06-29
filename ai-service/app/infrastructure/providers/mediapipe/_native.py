"""Helpers for taming MediaPipe's native (C/C++) layer."""

import contextlib
import os
import sys
from collections.abc import Iterator


@contextlib.contextmanager
def suppress_native_stderr() -> Iterator[None]:
    """Silence native (absl/glog/TFLite) writes to stderr during a call.

    MediaPipe's C++ bindings write informational warnings straight to the OS
    stderr file descriptor (e.g. "Created TensorFlow Lite XNNPACK delegate").
    Our application logs go to stdout, so redirecting fd 2 for the duration of a
    native call keeps startup output clean without hiding our own logs or Python
    exceptions (which propagate normally).
    """
    sys.stderr.flush()
    saved_stderr_fd = os.dup(2)
    devnull_fd = os.open(os.devnull, os.O_WRONLY)
    try:
        os.dup2(devnull_fd, 2)
        yield
    finally:
        os.dup2(saved_stderr_fd, 2)
        os.close(devnull_fd)
        os.close(saved_stderr_fd)
