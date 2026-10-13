import { connection } from 'next/server';
import { fetchAssessmentProcess, type ProcessResult, type PreviewParams } from './assessment-process';

function apiUrl(): string {
  return process.env.PROCESSES_API_URL ?? new URL('processes', process.env.LANDING_API_URL ?? 'http://127.0.0.1:8000/api/landing-page').href;
}

export async function getAssessmentProcess(): Promise<ProcessResult> {
  await connection();
  return fetchAssessmentProcess(apiUrl());
}

export async function getAssessmentPreview(params: PreviewParams): Promise<ProcessResult> {
  await connection();
  return fetchAssessmentProcess(apiUrl(), params);
}
