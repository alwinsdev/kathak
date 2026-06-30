"""Aggregate API router."""

from fastapi import APIRouter

from app.api.routes import classify, health, landmarks

api_router = APIRouter()
api_router.include_router(health.router)
api_router.include_router(landmarks.router)
api_router.include_router(classify.router)
