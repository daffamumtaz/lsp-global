'use client';

import type { ProcessResult } from '@/lib/assessment-process';
import { defaultInformationContent, type InformationContent } from '@/lib/information-copy';
import { CertificationStepper } from './certification-stepper';
import styles from './certification-stepper.module.css';

export function AssessmentProcessView({ process, unavailable, preview = false, copy = defaultInformationContent.flow, headingId }: ProcessResult & { preview?: boolean; copy?: InformationContent['flow']; headingId?: string }) {
  return <div>
    <div className={styles.heading}>
      <h2 id={headingId}>{process?.title ?? 'Alur Sertifikasi Jarak Jauh (SJJ)'}</h2>
      {process?.description && <p>{process.description}</p>}
    </div>
    {preview && process && <p className={styles.hint}>{copy.preview}</p>}
    {process
      ? <CertificationStepper stages={process.stages} preview={preview} />
      : <p className={styles.hint} role="status">{unavailable ? (preview ? copy.preview_unavailable : copy.unavailable) : copy.empty}</p>}
  </div>;
}
