"""Image codec unit tests (Pillow-based; no MediaPipe)."""

import io

import pytest
from PIL import Image

from app.core.exceptions import (
    InvalidImageError,
    PayloadTooLargeError,
    UnsupportedMediaTypeError,
)
from app.infrastructure.image.codec import decode_image


def _image_bytes(fmt: str, size: tuple[int, int] = (32, 32)) -> bytes:
    buffer = io.BytesIO()
    Image.new("RGB", size).save(buffer, format=fmt)
    return buffer.getvalue()


def test_decode_valid_png() -> None:
    array, width, height = decode_image(_image_bytes("PNG"), max_dimension=4096)
    assert (width, height) == (32, 32)
    assert array.shape == (32, 32, 3)


def test_decode_valid_jpeg() -> None:
    _, width, height = decode_image(_image_bytes("JPEG"), max_dimension=4096)
    assert (width, height) == (32, 32)


def test_decode_empty_raises_invalid() -> None:
    with pytest.raises(InvalidImageError):
        decode_image(b"", max_dimension=4096)


def test_decode_corrupted_raises_invalid() -> None:
    with pytest.raises(InvalidImageError):
        decode_image(b"this is not an image", max_dimension=4096)


def test_decode_unsupported_format_raises_415() -> None:
    with pytest.raises(UnsupportedMediaTypeError):
        decode_image(_image_bytes("GIF"), max_dimension=4096)


def test_decode_oversize_dimension_raises_413() -> None:
    with pytest.raises(PayloadTooLargeError):
        decode_image(_image_bytes("PNG", size=(50, 50)), max_dimension=10)
