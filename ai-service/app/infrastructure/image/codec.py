"""Image decoding + validation (infrastructure).

Decodes an encoded image (JPEG/PNG/WebP/BMP) into an RGB numpy array, enforcing
format, dimension and decodability rules. Raises typed domain errors so the API
can map them to consistent HTTP responses. Knows nothing about MediaPipe.
"""

import io

import numpy as np
from PIL import Image, UnidentifiedImageError

from app.core.exceptions import (
    InvalidImageError,
    PayloadTooLargeError,
    UnsupportedMediaTypeError,
)

SUPPORTED_FORMATS = {"JPEG", "PNG", "WEBP", "BMP"}


def decode_image(image_bytes: bytes, max_dimension: int) -> tuple[np.ndarray, int, int]:
    """Return (rgb_array, width, height) for a valid image, else raise.

    - empty payload            -> InvalidImageError (400)
    - undecodable bytes        -> InvalidImageError (400)
    - unsupported format       -> UnsupportedMediaTypeError (415)
    - dimensions too large     -> PayloadTooLargeError (413)
    """
    if not image_bytes:
        raise InvalidImageError("Empty image payload.")

    try:
        with Image.open(io.BytesIO(image_bytes)) as image:
            image_format = image.format
            if image_format not in SUPPORTED_FORMATS:
                raise UnsupportedMediaTypeError(f"Unsupported image format: {image_format}.")

            width, height = image.size
            if width > max_dimension or height > max_dimension:
                raise PayloadTooLargeError(
                    f"Image dimensions {width}x{height} exceed the maximum of {max_dimension}px."
                )

            rgb_array = np.asarray(image.convert("RGB"), dtype=np.uint8)
    except UnidentifiedImageError as error:
        raise InvalidImageError("Could not decode image.") from error

    return rgb_array, width, height
