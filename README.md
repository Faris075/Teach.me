# Teach.me

Teach.me is a social learning platform that blends LMS workflows with marketplace-style discovery inspired by Udemy/Coursera.

## New Core Capabilities

- Student friend graph with request/accept/decline lifecycle.
- Friend-to-course invites so learners can bring peers into enrolled courses.
- Course progress comparison across friends in the same course.
- AI course recommendation chatbot endpoint with AWS Bedrock primary provider and local fallback.
- Recommendation interaction logging for auditing and analytics.

## AWS Integration Points

- AWS Bedrock for AI recommendation responses.
- AWS SES for outbound notification email (existing notification stack).
- AWS S3-compatible asset pipeline for course media and distribution.

## Environment Variables

Add these values in `.env` for cloud-backed AI recommendations:

- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_DEFAULT_REGION`
- `AWS_BEDROCK_REGION`
- `AWS_BEDROCK_MODEL_ID`
- `AWS_COURSE_BUCKET`
- `AWS_COURSE_CDN_URL`