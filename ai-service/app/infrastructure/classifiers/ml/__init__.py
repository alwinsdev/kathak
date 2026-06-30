"""Placeholder for future ML-based ``MudraClassifier`` providers.

Reserved for later phases (e.g. TensorFlow, ONNX Runtime, PyTorch, XGBoost,
Random Forest). Each will implement the existing ``MudraClassifier`` contract and
register in ``infrastructure/classifiers/factory.py`` — the single composition
point — so no domain or application code changes when one is added.

Intentionally empty: this phase ships no ML inference (no TensorFlow / PyTorch /
ONNX), only the architecture they will slot into.
"""
