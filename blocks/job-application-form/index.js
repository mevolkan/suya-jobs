// First, create a new file: blocks/job-application-form/index.js
import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { RawHTML } from '@wordpress/element';

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
            // This code runs in the editor
            const interval = setInterval(() => {
                const jobPositionField = document.querySelector('input[name="field11[]"]');
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
                            const jobPositionField = document.querySelector('input[name="field11[]"]');
                            if (jobPositionField) {
                                jobPositionField.value = ${JSON.stringify(wp.data.select('core/editor').getEditedPostAttribute('title'))};
                            }
                        });
                    `
                }} />
            </div>
        );
    }
});