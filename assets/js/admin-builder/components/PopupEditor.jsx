import React, { useState, useEffect } from 'react';

const PopupEditor = ({ popup, templates, onSave, onCancel }) => {
  const [formData, setFormData] = useState(popup);

  useEffect(() => {
    setFormData(popup);
  }, [popup]);

  const handleChange = (path, value) => {
    const keys = path.split('.');
    const newData = { ...formData };
    let current = newData;

    for (let i = 0; i < keys.length - 1; i++) {
      if (!current[keys[i]]) {
        current[keys[i]] = {};
      }
      current = current[keys[i]];
    }

    current[keys[keys.length - 1]] = value;
    setFormData(newData);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    onSave(formData);
  };

  const addField = () => {
    const newFields = [...(formData.config.fields || []), {
      type: 'text',
      name: `field_${Date.now()}`,
      label: 'New Field',
      required: false,
      placeholder: '',
    }];
    handleChange('config.fields', newFields);
  };

  const removeField = (index) => {
    const newFields = formData.config.fields.filter((_, i) => i !== index);
    handleChange('config.fields', newFields);
  };

  return (
    <div className="convertlab-popup-editor">
      <div className="convertlab-editor-header">
        <h2>{formData.id ? 'Edit Popup' : 'Create New Popup'}</h2>
        <div>
          <button type="button" className="button" onClick={onCancel}>
            Cancel
          </button>
          <button type="submit" form="popup-form" className="button button-primary">
            Save Popup
          </button>
        </div>
      </div>

      <form id="popup-form" onSubmit={handleSubmit}>
        <div className="convertlab-editor-sections">
          {/* Basic Info */}
          <div className="convertlab-section">
            <h3>Basic Information</h3>
            <table className="form-table">
              <tbody>
                <tr>
                  <th><label>Title</label></th>
                  <td>
                    <input
                      type="text"
                      value={formData.title || ''}
                      onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                      className="regular-text"
                      required
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          {/* Design */}
          <div className="convertlab-section">
            <h3>Design</h3>
            <table className="form-table">
              <tbody>
                <tr>
                  <th><label>Title</label></th>
                  <td>
                    <input
                      type="text"
                      value={formData.config.design.title || ''}
                      onChange={(e) => handleChange('config.design.title', e.target.value)}
                      className="regular-text"
                    />
                  </td>
                </tr>
                <tr>
                  <th><label>Text</label></th>
                  <td>
                    <textarea
                      value={formData.config.design.text || ''}
                      onChange={(e) => handleChange('config.design.text', e.target.value)}
                      rows="4"
                      className="large-text"
                    />
                  </td>
                </tr>
                <tr>
                  <th><label>Background Color</label></th>
                  <td>
                    <input
                      type="color"
                      value={formData.config.design.background_color || '#ffffff'}
                      onChange={(e) => handleChange('config.design.background_color', e.target.value)}
                    />
                  </td>
                </tr>
                <tr>
                  <th><label>Button Text</label></th>
                  <td>
                    <input
                      type="text"
                      value={formData.config.design.button_text || 'Submit'}
                      onChange={(e) => handleChange('config.design.button_text', e.target.value)}
                      className="regular-text"
                    />
                  </td>
                </tr>
                <tr>
                  <th><label>Button Color</label></th>
                  <td>
                    <input
                      type="color"
                      value={formData.config.design.button_color || '#0073aa'}
                      onChange={(e) => handleChange('config.design.button_color', e.target.value)}
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          {/* Form Fields */}
          <div className="convertlab-section">
            <h3>Form Fields</h3>
            <div className="convertlab-fields-list">
              {formData.config.fields && formData.config.fields.map((field, index) => (
                <div key={index} className="convertlab-field-item">
                  <select
                    value={field.type}
                    onChange={(e) => {
                      const newFields = [...formData.config.fields];
                      newFields[index].type = e.target.value;
                      handleChange('config.fields', newFields);
                    }}
                  >
                    <option value="email">Email</option>
                    <option value="text">Text</option>
                    <option value="name">Name</option>
                    <option value="phone">Phone</option>
                  </select>
                  <input
                    type="text"
                    placeholder="Label"
                    value={field.label || ''}
                    onChange={(e) => {
                      const newFields = [...formData.config.fields];
                      newFields[index].label = e.target.value;
                      handleChange('config.fields', newFields);
                    }}
                  />
                  <input
                    type="text"
                    placeholder="Placeholder"
                    value={field.placeholder || ''}
                    onChange={(e) => {
                      const newFields = [...formData.config.fields];
                      newFields[index].placeholder = e.target.value;
                      handleChange('config.fields', newFields);
                    }}
                  />
                  <label>
                    <input
                      type="checkbox"
                      checked={field.required || false}
                      onChange={(e) => {
                        const newFields = [...formData.config.fields];
                        newFields[index].required = e.target.checked;
                        handleChange('config.fields', newFields);
                      }}
                    />
                    Required
                  </label>
                  <button
                    type="button"
                    className="button button-small"
                    onClick={() => removeField(index)}
                  >
                    Remove
                  </button>
                </div>
              ))}
              <button type="button" className="button" onClick={addField}>
                Add Field
              </button>
            </div>
          </div>

          {/* Triggers */}
          <div className="convertlab-section">
            <h3>Display Triggers</h3>
            <table className="form-table">
              <tbody>
                <tr>
                  <th><label>Page Targeting</label></th>
                  <td>
                    <select
                      value={formData.config.triggers.page_targeting || 'all'}
                      onChange={(e) => handleChange('config.triggers.page_targeting', e.target.value)}
                    >
                      <option value="all">All Pages</option>
                      <option value="homepage">Homepage Only</option>
                      <option value="product">Product Pages Only</option>
                    </select>
                  </td>
                </tr>
                <tr>
                  <th><label>Time Delay (seconds)</label></th>
                  <td>
                    <input
                      type="number"
                      value={formData.config.triggers.time_delay || 0}
                      onChange={(e) => handleChange('config.triggers.time_delay', parseInt(e.target.value) || 0)}
                      min="0"
                      className="small-text"
                    />
                  </td>
                </tr>
                <tr>
                  <th><label>Scroll Percent</label></th>
                  <td>
                    <input
                      type="number"
                      value={formData.config.triggers.scroll_percent || 0}
                      onChange={(e) => handleChange('config.triggers.scroll_percent', parseInt(e.target.value) || 0)}
                      min="0"
                      max="100"
                      className="small-text"
                    />
                    <p className="description">Show popup when user scrolls this percentage of the page</p>
                  </td>
                </tr>
                <tr>
                  <th><label>Show Once</label></th>
                  <td>
                    <label>
                      <input
                        type="checkbox"
                        checked={formData.config.triggers.show_once || false}
                        onChange={(e) => handleChange('config.triggers.show_once', e.target.checked)}
                      />
                      Show popup only once per session
                    </label>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          {/* Thank You */}
          <div className="convertlab-section">
            <h3>Thank You Message</h3>
            <table className="form-table">
              <tbody>
                <tr>
                  <th><label>Message</label></th>
                  <td>
                    <textarea
                      value={formData.config.thank_you.message || ''}
                      onChange={(e) => handleChange('config.thank_you.message', e.target.value)}
                      rows="3"
                      className="large-text"
                    />
                  </td>
                </tr>
                <tr>
                  <th><label>Redirect URL (optional)</label></th>
                  <td>
                    <input
                      type="url"
                      value={formData.config.thank_you.redirect || ''}
                      onChange={(e) => handleChange('config.thank_you.redirect', e.target.value)}
                      className="regular-text"
                      placeholder="https://example.com/thank-you"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </form>
    </div>
  );
};

export default PopupEditor;

