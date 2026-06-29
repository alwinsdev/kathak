-- Module A — AI-Verified Completion
-- Run once on an existing kathak_therapy database (new installs already get this via schema.sql).
-- Completion is AI-verified only, so no `source` column is needed — just the AI confidence.
USE kathak_therapy;

ALTER TABLE completions
  ADD COLUMN confidence DECIMAL(4,3) NULL AFTER notes;
