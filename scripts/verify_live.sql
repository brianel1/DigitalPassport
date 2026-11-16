USE digitalpassport_live;
SELECT 'students' AS kind, COUNT(*) AS total FROM users WHERE role = 'student'
UNION ALL SELECT 'assessments', COUNT(*) FROM assessments
UNION ALL SELECT 'questions', COUNT(*) FROM assessment_questions
UNION ALL SELECT 'live_jobs', COUNT(*) FROM job_listings
UNION ALL SELECT 'matches', COUNT(*) FROM job_matches
UNION ALL SELECT 'cvs', COUNT(*) FROM generated_cvs;
SELECT source, COUNT(*) AS total FROM job_listings GROUP BY source;
