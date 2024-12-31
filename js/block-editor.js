const { registerPlugin } = wp.plugins;
const { PluginDocumentSettingPanel } = wp.editPost;
const { TextControl, DatePicker } = wp.components;
const { useSelect, useDispatch } = wp.data;
const { useEffect, useState } = wp.element;
const { useBlockProps } = wp.blockEditor;

const JobMetaFields = () => {
  const postType = useSelect((select) =>
    select("core/editor").getCurrentPostType()
  );

  if (postType !== "job") {
    return null;
  }

  const { editPost } = useDispatch("core/editor");
  const meta = useSelect((select) =>
    select("core/editor").getEditedPostAttribute("meta")
  );

  return (
    <>
      <PluginDocumentSettingPanel
        name="job-details"
        title="Job Details"
        initialOpen={true}
      >
        <div className="job-meta-fields">
          <DatePicker
            label="Close Date"
            currentDate={meta._close_date}
            onChange={(date) => {
              editPost({ meta: { _close_date: date } });
            }}
          />

          <TextControl
            label="Location"
            value={meta._location || ""}
            onChange={(value) => {
              editPost({ meta: { _location: value } });
            }}
          />

          <div className="job-type-selector">
            <label>Job Type</label>
            {[
              "contract",
              "freelance",
              "full_time",
              "part_time",
              "internship",
              "temporary",
            ].map((type) => (
              <div key={type}>
                <input
                  type="radio"
                  id={`job-type-${type}`}
                  name="job_type"
                  value={type}
                  checked={meta._job_type === type}
                  onChange={() => {
                    editPost({ meta: { _job_type: type } });
                  }}
                />
                <label htmlFor={`job-type-${type}`}>
                  {type
                    .replace("_", " ")
                    .replace(/\b\w/g, (l) => l.toUpperCase())}
                </label>
              </div>
            ))}
          </div>
        </div>
      </PluginDocumentSettingPanel>
      <PluginDocumentSettingPanel
        name="job-form-settings"
        title="Application Form Settings"
        initialOpen={true}
      >
        <p>
          To add the application form to your job post, insert the "Job
          Application Form" block in the editor. The position field will be
          automatically filled with the job title.
        </p>
      </PluginDocumentSettingPanel>
    </>
  );
};

registerPlugin("suya-jobs-meta", {
  render: JobMetaFields,
  icon: "businessperson",
});
