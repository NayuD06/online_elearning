<?php

namespace Database\Seeders;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Demo logins. Every password follows the app rule: 6+ characters, an uppercase letter and a special character.
     */
    public const string ADMIN_PASSWORD = 'Admin@123';

    public const string TEACHER_PASSWORD = 'Teacher@123';

    public const string STUDENT_PASSWORD = 'Student@123';

    /**
     * Chapters of every demo course, each one => its lesson titles.
     *
     * @var array<string, list<string>>
     */
    private const array OUTLINE = [
        'Chương 1: Làm quen' => ['Giới thiệu tổng quan', 'Cài đặt môi trường'],
        'Chương 2: Kiến thức nền tảng' => ['Kiến thức nền tảng', 'Thực hành cơ bản'],
        'Chương 3: Vận dụng' => ['Bài tập tình huống', 'Dự án cuối khoá'],
    ];

    /**
     * Courses of teacher@example.com that show the review flow: [title, summary, category, status, rejection reason].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: CourseStatus, 4: ?string}>
     */
    private const array REVIEW_DEMO_COURSES = [
        ['Lập trình Python nâng cao', 'Lập trình hướng đối tượng, xử lý file và làm việc với API bằng Python.', 'Khoa học dữ liệu', CourseStatus::Pending, null],
        ['Docker cho lập trình viên', 'Đóng gói ứng dụng bằng Docker và chạy nhiều dịch vụ với Docker Compose.', 'Lập trình Web', CourseStatus::Draft, null],
        ['Viết CV và phỏng vấn xin việc', 'Chuẩn bị hồ sơ xin việc ấn tượng và tự tin khi phỏng vấn.', 'Kỹ năng mềm', CourseStatus::Rejected, 'Mô tả khoá học còn sơ sài, vui lòng bổ sung mục tiêu và đề cương chi tiết cho từng chương.'],
    ];

    /**
     * Demo catalog: category => list of [title, summary, level, price].
     *
     * @var array<string, list<array{0: string, 1: string, 2: CourseLevel, 3: int}>>
     */
    private const array CATALOG = [
        'Lập trình Web' => [
            ['Lập trình PHP & MySQL từ con số 0', 'Nắm vững PHP thuần, kết nối MySQL và xây dựng website động hoàn chỉnh.', CourseLevel::Beginner, 0],
            ['Laravel 13 cho người mới bắt đầu', 'Xây dựng ứng dụng web theo mô hình MVC với Laravel, Eloquent và Blade.', CourseLevel::Intermediate, 499000],
            ['HTML, CSS & Tailwind thực chiến', 'Thiết kế giao diện responsive hiện đại bằng HTML5, CSS3 và Tailwind CSS.', CourseLevel::Beginner, 199000],
        ],
        'Cơ sở dữ liệu' => [
            ['Thiết kế cơ sở dữ liệu quan hệ', 'Phân tích yêu cầu, vẽ sơ đồ ERD và chuẩn hoá dữ liệu đến 3NF.', CourseLevel::Intermediate, 299000],
            ['SQL nâng cao cho lập trình viên', 'Truy vấn JOIN, subquery, index và tối ưu hiệu năng trên MySQL.', CourseLevel::Advanced, 399000],
        ],
        'Khoa học dữ liệu' => [
            ['Python cho phân tích dữ liệu', 'Làm quen với Python, Pandas và trực quan hoá dữ liệu bằng Matplotlib.', CourseLevel::Beginner, 0],
            ['Nhập môn Machine Learning', 'Hiểu các thuật toán học máy cơ bản và áp dụng với scikit-learn.', CourseLevel::Advanced, 599000],
        ],
        'Kỹ năng mềm' => [
            ['Kỹ năng thuyết trình hiệu quả', 'Tự tin trình bày ý tưởng trước đám đông với cấu trúc rõ ràng.', CourseLevel::Beginner, 0],
            ['Quản lý thời gian cho sinh viên', 'Lập kế hoạch học tập, tránh trì hoãn và cân bằng cuộc sống.', CourseLevel::Beginner, 99000],
        ],
        'Ngoại ngữ' => [
            ['Tiếng Anh giao tiếp cơ bản', 'Luyện phản xạ nghe nói qua các tình huống giao tiếp hằng ngày.', CourseLevel::Beginner, 249000],
            ['Luyện thi TOEIC 650+', 'Chiến lược làm bài và luyện đề TOEIC Listening & Reading.', CourseLevel::Intermediate, 449000],
        ],
        'Thiết kế' => [
            ['Thiết kế UI/UX với Figma', 'Từ wireframe đến prototype: quy trình thiết kế sản phẩm số.', CourseLevel::Intermediate, 349000],
        ],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin System',
            'password' => self::ADMIN_PASSWORD,
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $teachers = collect([
            ['Nguyễn Văn Minh', 'teacher@example.com'],
            ['Trần Thị Lan', 'lan.tran@example.com'],
            ['Lê Hoàng Phúc', 'phuc.le@example.com'],
        ])->map(fn (array $teacher) => User::updateOrCreate(['email' => $teacher[1]], [
            'name' => $teacher[0],
            'password' => self::TEACHER_PASSWORD,
            'role' => UserRole::Teacher,
            'bio' => 'Giảng viên với hơn 5 năm kinh nghiệm giảng dạy và làm việc thực tế.',
            'email_verified_at' => now(),
        ]));

        $students = collect([User::updateOrCreate(['email' => 'student@example.com'], [
            'name' => 'Phạm Minh Anh',
            'password' => self::STUDENT_PASSWORD,
            'role' => UserRole::Student,
            'email_verified_at' => now(),
        ])])->merge(User::factory(24)->create(['role' => UserRole::Student]));

        foreach (self::CATALOG as $categoryName => $courses) {
            $category = Category::firstOrCreate(['slug' => Str::slug($categoryName)], [
                'name' => $categoryName,
                'description' => "Các khoá học thuộc lĩnh vực {$categoryName}.",
            ]);

            foreach ($courses as [$title, $summary, $level, $price]) {
                $course = Course::firstOrCreate(['slug' => Str::slug($title)], [
                    'category_id' => $category->id,
                    'instructor_id' => $teachers->random()->id,
                    'title' => $title,
                    'summary' => $summary,
                    'description' => $this->courseDescription($title, $summary),
                    'price' => $price,
                    'level' => $level,
                    'status' => CourseStatus::Published,
                    'submitted_at' => now()->subDays(random_int(151, 160)),
                    'published_at' => now()->subDays(random_int(1, 150)),
                ]);

                if ($course->wasRecentlyCreated) {
                    $this->seedLessonsAndLearners($course, $students);
                }
            }
        }

        $this->seedReviewDemoCourses($teachers->first());
        $this->seedAnnouncements($admin);
    }

    /**
     * Courses waiting for review, still in draft or rejected, so the approval flow can be tried right away.
     */
    private function seedReviewDemoCourses(User $teacher): void
    {
        foreach (self::REVIEW_DEMO_COURSES as [$title, $summary, $categoryName, $status, $rejectionReason]) {
            $course = Course::firstOrCreate(['slug' => Str::slug($title)], [
                'category_id' => Category::where('slug', Str::slug($categoryName))->value('id'),
                'instructor_id' => $teacher->id,
                'title' => $title,
                'summary' => $summary,
                'description' => $this->courseDescription($title, $summary),
                'price' => 0,
                'level' => CourseLevel::Intermediate,
                'status' => $status,
                'rejection_reason' => $rejectionReason,
                'submitted_at' => $status === CourseStatus::Draft ? null : now()->subDays(random_int(1, 5)),
            ]);

            if ($course->wasRecentlyCreated) {
                $this->seedCurriculum($course);
            }
        }
    }

    private function courseDescription(string $title, string $summary): string
    {
        return <<<HTML
            <h2>Giới thiệu khoá học</h2>
            <p>{$summary} Khoá học <strong>{$title}</strong> được thiết kế theo lộ trình từ cơ bản đến thực hành, phù hợp với sinh viên và người đi làm.</p>
            <h3>Bạn sẽ học được gì?</h3>
            <ul>
                <li>Nắm vững kiến thức nền tảng và thuật ngữ quan trọng.</li>
                <li>Thực hành qua các bài tập và dự án nhỏ sát thực tế.</li>
                <li>Tự tin áp dụng vào học tập và công việc.</li>
            </ul>
            <h3>Yêu cầu</h3>
            <p>Máy tính kết nối Internet và tinh thần ham học hỏi.</p>
            HTML;
    }

    /**
     * Chapters with two lessons and a three-question quiz each.
     *
     * @return Collection<int, Lesson>
     */
    private function seedCurriculum(Course $course): Collection
    {
        $lessons = collect();
        $lessonNumber = 1;

        foreach (array_keys(self::OUTLINE) as $sectionIndex => $sectionTitle) {
            $section = $course->sections()->create(['title' => $sectionTitle, 'position' => $sectionIndex + 1]);

            foreach (self::OUTLINE[$sectionTitle] as $lessonIndex => $lessonTitle) {
                $lessons->push($section->lessons()->create([
                    'course_id' => $course->id,
                    'title' => 'Bài '.$lessonNumber.': '.$lessonTitle,
                    'content' => "<p>Nội dung bài học <strong>{$lessonTitle}</strong> của khoá {$course->title}.</p><p>Hãy xem video, đọc tài liệu và hoàn thành bài tập trước khi chuyển sang bài tiếp theo.</p><blockquote>Mẹo: ghi chú lại những ý chính để ôn tập dễ dàng hơn.</blockquote>",
                    'video_url' => $lessonNumber === 1 ? 'https://www.youtube.com/watch?v=ysEN5RaKOlA' : null,
                    'duration_minutes' => random_int(8, 40),
                    'position' => $lessonIndex + 1,
                    'is_preview' => $lessonNumber === 1,
                ]));
                $lessonNumber++;
            }

            $this->seedQuiz($course, $section);
        }

        return $lessons;
    }

    private function seedQuiz(Course $course, Section $section): Quiz
    {
        $quiz = $section->quizzes()->create([
            'course_id' => $course->id,
            'title' => 'Kiểm tra '.Str::before($section->title, ':'),
            'description' => 'Ôn lại kiến thức của '.Str::lower(Str::before($section->title, ':')).' trước khi học tiếp.',
            'pass_percent' => 60,
        ]);

        $questions = [
            ["Nội dung chính của \"{$section->title}\" thuộc khoá học nào?", [$course->title, 'Nhập môn Kế toán', 'Lịch sử Việt Nam', 'Vật lý đại cương']],
            ['Cách học hiệu quả nhất với mỗi bài học là gì?', ['Xem video, ghi chú và làm bài tập', 'Chỉ đọc tiêu đề bài học', 'Bỏ qua phần thực hành', 'Học dồn vào cuối khoá']],
            ['Khi nào nên bấm "Đánh dấu hoàn thành" một bài học?', ['Sau khi đã học xong và nắm được nội dung bài', 'Ngay khi mở bài học', 'Không bao giờ cần bấm', 'Chỉ khi giảng viên yêu cầu']],
        ];

        foreach ($questions as $position => [$content, $answers]) {
            $question = $quiz->questions()->create(['content' => $content, 'position' => $position + 1]);

            // The first answer is the correct one; shuffle so it is not always option A.
            $question->answers()->createMany(collect($answers)
                ->map(fn (string $answer, int $index) => ['content' => $answer, 'is_correct' => $index === 0])
                ->shuffle()
                ->all());
        }

        return $quiz;
    }

    /**
     * @param  Collection<int, User>  $students
     */
    private function seedLessonsAndLearners(Course $course, $students): void
    {
        $lessons = $this->seedCurriculum($course);
        $quizzes = $course->quizzes()->get();

        foreach ($students->random(random_int(4, 12)) as $student) {
            $student->enrollments()->forceCreate([
                'course_id' => $course->id,
                'created_at' => now()->subDays(random_int(0, 170)),
            ]);

            $finishedLessons = $lessons->take(random_int(0, $lessons->count()));
            $student->completedLessons()->syncWithoutDetaching($finishedLessons->pluck('id'));

            if ($finishedLessons->count() === $lessons->count()) {
                $student->enrollments()->where('course_id', $course->id)->update(['completed_at' => now()->subDays(random_int(0, 30))]);
            }

            // Learners who finished a chapter have taken its quiz once.
            foreach ($quizzes as $quiz) {
                if ($finishedLessons->where('section_id', $quiz->section_id)->count() === 2) {
                    $quiz->grade($student, $quiz->questions()->with('answers')->get()->mapWithKeys(fn ($question) => [
                        $question->id => (random_int(0, 3) ? $question->answers->firstWhere('is_correct', true) : $question->answers->first())->id,
                    ])->all());
                }
            }

            if (random_int(0, 1)) {
                $student->reviews()->create([
                    'course_id' => $course->id,
                    'rating' => random_int(3, 5),
                    'comment' => collect([
                        'Khoá học rất bổ ích, giảng viên giải thích dễ hiểu.',
                        'Nội dung thực tế, mình áp dụng được ngay vào đồ án.',
                        'Bài giảng chi tiết, mong có thêm bài tập nâng cao.',
                        'Rất đáng tiền, cảm ơn thầy cô!',
                    ])->random(),
                ]);
            }
        }
    }

    private function seedAnnouncements(User $admin): void
    {
        $announcements = [
            'Thông báo lịch học kỳ I năm học 2026 - 2027',
            'Ra mắt lộ trình học Lập trình Web Full-stack',
            'Hướng dẫn ghi danh và theo dõi tiến độ học tập',
            'Bảo trì hệ thống định kỳ cuối tháng',
            'Cuộc thi "Sáng tạo cùng E-Learning" chính thức khởi động',
        ];

        foreach ($announcements as $index => $title) {
            Announcement::firstOrCreate(['slug' => Str::slug($title)], [
                'user_id' => $admin->id,
                'title' => $title,
                'content' => "<p>{$title}. Sinh viên vui lòng theo dõi thường xuyên mục <strong>Thông báo</strong> để cập nhật thông tin mới nhất.</p><ul><li>Thời gian áp dụng: ngay khi thông báo được đăng.</li><li>Mọi thắc mắc xin liên hệ phòng Đào tạo.</li></ul>",
                'is_published' => true,
                'published_at' => now()->subDays($index * 3),
            ]);
        }
    }
}
