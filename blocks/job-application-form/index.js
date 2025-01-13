const { registerBlockType } = wp.blocks;
const { useSelect } = wp.data;
const { useEffect } = wp.element;
const { RawHTML } = wp.element;

registerBlockType('suya-jobs/job-application-form', {
    title: 'Job Application Form',
    icon: 'feedback',
    category: 'widgets',
    supports: {
        html: false
    },
    edit: function Edit() {
        const postTitle = useSelect(select => 
            select('core/editor').getEditedPostAttribute('title')
        );

        useEffect(() => {
            const interval = setInterval(() => {
                const jobPositionField = document.querySelector('input[name="field18[]"]');
                if (jobPositionField) {
                    jobPositionField.value = postTitle;
                    clearInterval(interval);
                }
            }, 1000);

            return () => clearInterval(interval);
        }, [postTitle]);

        return (
            <div className="wp-block-suya-jobs-application-form">
                <RawHTML>
                    {`[fc id='1'][/fc]`}
                </RawHTML>
                <div className="editor-notice">
                    Job Application Form - Position will be auto-filled with: {postTitle}
                </div>
            </div>
        );
    },
    save: function Save() {
        return (
            <div className="wp-block-suya-jobs-application-form">
                <RawHTML>
                    {`[fc id='1'][/fc]`}
                </RawHTML>
                <script dangerouslySetInnerHTML={{
                    __html: `
                        document.addEventListener('DOMContentLoaded', function() {
                            const jobPositionField = document.querySelector('input[name="field18[]"]');
                            if (jobPositionField) {
                                jobPositionField.value = document.title.split(' - ')[0];
                            }
                        });
                    `
                }} />
            </div>
        );
    }
});