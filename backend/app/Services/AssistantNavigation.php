<?php

namespace App\Services;

use App\Models\User;

/** Product navigation is deterministic: a model cannot invent or withhold these destinations. */
class AssistantNavigation
{
    public function actions(User $user, bool $malay = false, ?int $cvId = null): array
    {
        $items = $user->role === 'admin' ? [
            'reviews' => ['Open review queue', 'Buka semakan bukti', '/admin/reviews'],
            'assessments' => ['Manage assessments', 'Urus penilaian', '/admin/assessments'],
            'students' => ['Manage students', 'Urus pelajar', '/admin/users'],
            'dashboard' => ['Open overview', 'Buka overview', '/admin/dashboard'],
        ] : [
            'cv' => ['Open my CVs', 'Buka CV saya', '/student/cv'],
            'cv_upload' => ['Upload your CV', 'Muat naik CV', '/student/cv?view=upload'],
            'cv_create' => ['Create a CV', 'Cipta CV', '/student/cv?view=create'],
            'cv_review' => ['Review my CV', 'Semak CV saya', '/student/cv?view=review'],
            'skills' => ['Open my skills', 'Buka kemahiran saya', '/student/skills'],
            'projects' => ['Open projects', 'Buka projek', '/student/projects'],
            'certifications' => ['Open certifications', 'Buka sijil', '/student/certifications'],
            'profile' => ['Edit personal details', 'Edit maklumat diri', '/student/profile'],
            'skills_profile' => ['Open skills profile', 'Buka profil kemahiran', '/student/skills-profile'],
            'jobs' => ['Find job matches', 'Cari padanan kerja', '/student/jobs'],
            'assessments' => ['Open assessments', 'Buka penilaian', '/student/assessments'],
        ];
        if ($user->role === 'student' && $cvId) {
            $items['cv'][2] .= '?cv='.$cvId;
            $items['cv_review'][2] .= '&cv='.$cvId;
        }
        return array_map(fn ($item) => ['label' => $item[$malay ? 1 : 0], 'path' => $item[2]], $items);
    }

    public function isMalay(string $message): bool
    {
        return (bool) preg_match('/\b(aku|saya|nak|boleh|apa|kau|tolong|mana|kat|dekat|macam|bagaimana|cemana|mcm|muat|sijil|kemahiran)\b/iu', $message);
    }

    public function destination(User $user, string $message): ?string
    {
        if (mb_strlen($message) > 350) return null;
        // Content advice and definitions are not requests to open a page.
        if (preg_match('/\b(what is|what are|what does|apa tu|ape tu|apa itu|apa beza|difference|explain|contoh|example|wording|write|tulis|cerita|letak|improve|review my wording)\b/iu', $message)) return null;
        // Only clear requests to find/use a feature. Writing advice still goes to AI.
        $where = preg_match('/\b(where|whereabouts|mana|dimana|katne|dekatne)\b/iu', $message);
        $operation = preg_match('/\b(upload|import|create|open|find|access|navigate|submit|download|export|rename|change|share|edit|review|verify|verified|tailor|buka|cari|hantar|muat|cipta|tukar|semak|sahkan|kongsi)\b/iu', $message);
        $request = preg_match('/\b(how|can i|could i|take me|bring me|go to|show me|i want|i need|nak|nk|boleh|macam|bagaimana|cemana|camne|cane|mcm|please|open|buka)\b/iu', $message);
        if (!$where && !($operation && $request)) return null;
        if ($user->role === 'admin') {
            if (preg_match('/\b(assessment|assessments|penilaian|quiz|quizzes)\b/iu', $message)) return 'assessments';
            if (preg_match('/\b(review|reviews|evidence|verify|verification|bukti|semakan)\b/iu', $message)) return 'reviews';
            if (preg_match('/\b(student|students|user|users|pelajar|account|accounts)\b/iu', $message)) return 'students';
            if (preg_match('/\b(overview|dashboard|health|status)\b/iu', $message)) return 'dashboard';
            return null;
        }
        if (preg_match('/\b(cv|cvs|resume|résumé)\b/iu', $message)) {
            if (preg_match('/\b(tailor|company|specific role|syarikat)\b/iu', $message)) return 'jobs';
            if (preg_match('/\b(upload|import|muat|unggah)\b/iu', $message)) return 'cv_upload';
            if (preg_match('/\b(create|build|buat|cipta)\b/iu', $message)) return 'cv_create';
            if (preg_match('/\b(review|semak)\b/iu', $message)) return 'cv_review';
            if ($where || preg_match('/\b(open|buka|download|export|rename|edit|change|tukar|muat turun)\b/iu', $message)) return 'cv';
            return null;
        }
        if (preg_match('/\b(certification|certifications|certificate|certificates|sijil)\b/iu', $message)) return 'certifications';
        if (preg_match('/\b(project|projects|projek|evidence|bukti)\b/iu', $message)) return 'projects';
        if (preg_match('/skills profile|profil kemahiran|\b(share|sharing|qr|kongsi)\b/iu', $message)) return 'skills_profile';
        if (preg_match('/\b(job|jobs|kerja|internship|internships|tailor)\b/iu', $message)) return 'jobs';
        if (preg_match('/\b(assessment|assessments|penilaian|quiz|quizzes|test)\b/iu', $message)) return 'assessments';
        if (preg_match('/\b(skill|skills|kemahiran|verify|verified)\b/iu', $message)) return 'skills';
        if (preg_match('/\b(profile|profil|phone|email|name|nama|telefon|personal)\b/iu', $message)) return 'profile';
        return null;
    }

    public function reply(User $user, string $key, bool $malay, ?int $cvId = null): array
    {
        $guides = [
            'cv_upload' => ['Open **Upload your CV** below. Name your document, then choose a PDF, DOCX or TXT file (up to 2 MB), or paste your text. After extraction, check **Source text**, then choose **Organise with AI**.', 'Klik **Muat naik CV** di bawah. Namakan dokumen, kemudian pilih fail PDF, DOCX atau TXT (maksimum 2 MB), atau tampal teks. Selepas teks dibaca, semak **Source text**, kemudian pilih **Organise with AI**.'],
            'cv_create' => ['Open **Create a CV** below. Give your document a name, then confirm to start a draft from your verified records. You can edit and review it before downloading.', 'Klik **Cipta CV** di bawah. Namakan dokumen, kemudian sahkan untuk mula draf daripada rekod yang disahkan. Anda boleh edit dan semak sebelum muat turun.'],
            'cv_review' => ['Open **Review my CV** below. Choose your saved document, then select **Review my CV** to run its checks. If you do not have a document yet, create or upload one first.', 'Klik **Semak CV saya** di bawah. Pilih dokumen yang disimpan, kemudian tekan **Review my CV** untuk menjalankan semakan. Jika belum ada dokumen, cipta atau muat naik dahulu.'],
            'cv' => ['Open **My CVs** below and choose your document. Edit its sections, use **Save name** to rename it, or choose **Download** for PDF or Word.', 'Buka CV di bawah dan pilih dokumen anda. Edit bahagian yang diperlukan, gunakan **Save name** untuk tukar nama, atau **Download** untuk PDF atau Word.'],
            'skills' => ['Open **My skills** below. Add a skill and link supporting project or certification evidence, then submit it for administrator review. Uploading a CV alone does not verify a skill.', 'Buka kemahiran di bawah. Tambah kemahiran dan pautkan bukti projek atau sijil, kemudian hantar untuk semakan pentadbir. Memuat naik CV sahaja tidak mengesahkan kemahiran.'],
            'projects' => ['Open **Projects** below to add or edit a project, attach evidence and link its skills. Submit it for review when the details are ready.', 'Buka projek di bawah untuk tambah atau edit projek, lampirkan bukti dan pautkan kemahiran. Hantar untuk semakan apabila maklumat lengkap.'],
            'certifications' => ['Open **Certifications** below. Add the certificate details and supporting file, link the relevant skills, then submit it for review.', 'Buka sijil di bawah. Masukkan maklumat sijil dan fail bukti, pautkan kemahiran berkaitan, kemudian hantar untuk semakan.'],
            'profile' => ['Open **Personal details** below to update your name, contact information and programme, then save your changes.', 'Buka maklumat diri di bawah untuk kemas kini nama, maklumat hubungan dan program, kemudian simpan perubahan.'],
            'skills_profile' => ['Open **Skills profile** below to view or download your record. To share it, choose which sections to include and create an expiring link or QR. You can revoke it later.', 'Buka profil kemahiran di bawah untuk lihat atau muat turun rekod. Untuk berkongsi, pilih bahagian dan cipta pautan atau QR dengan tempoh sah. Anda boleh batalkan pautan kemudian.'],
            'jobs' => ['Open **Job matches** below, enter a role or skill and select **Search jobs**. To prepare a CV for a specific company, use **Tailor CV** on that listing; it creates a separate version.', 'Buka padanan kerja di bawah, masukkan jawatan atau kemahiran dan tekan **Search jobs**. Untuk CV khusus syarikat, pilih **Tailor CV** pada listing tersebut; versi berasingan akan dicipta.'],
            'assessments' => [$user->role === 'admin' ? 'Open **Manage assessments** below to create assessments, add questions and review them before publishing.' : 'Open **Assessments** below, choose an available assessment and read its instructions before starting. An unfinished attempt resumes with its original deadline.', $user->role === 'admin' ? 'Buka penilaian di bawah untuk cipta penilaian, tambah soalan dan semak sebelum publish.' : 'Buka penilaian di bawah, pilih penilaian dan baca arahan sebelum bermula. Percubaan yang belum tamat menggunakan deadline asal.'],
            'reviews' => ['Open the **Review queue** below. Choose a submission to inspect its evidence and history before recording your decision.', 'Buka semakan bukti di bawah. Pilih penghantaran untuk periksa bukti dan sejarah sebelum merekod keputusan.'],
            'students' => ['Open **Manage students** below to find an account and review its details or status.', 'Buka pengurusan pelajar di bawah untuk cari akaun dan semak maklumat atau statusnya.'],
            'dashboard' => ['Open **Overview** below for review activity and the system health panel.', 'Buka overview di bawah untuk aktiviti semakan dan panel kesihatan sistem.'],
        ];
        return ['role' => 'assistant', 'content' => $guides[$key][$malay ? 1 : 0], 'engine' => 'platform-guide',
            'sources' => [], 'actions' => [$this->actions($user, $malay, $cvId)[$key]], 'createdAt' => now()->toISOString()];
    }
}
