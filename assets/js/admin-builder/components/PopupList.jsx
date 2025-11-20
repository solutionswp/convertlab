import React from 'react';

const PopupList = ({ popups, onSelect, onCreateNew, onDelete }) => {
  return (
    <div className="convertlab-popup-list">
      <div className="convertlab-header">
        <h2>Popups</h2>
        <button className="button button-primary" onClick={onCreateNew}>
          Add New Popup
        </button>
      </div>

      {popups.length === 0 ? (
        <div className="convertlab-empty-state">
          <p>No popups yet. Create your first popup to get started!</p>
        </div>
      ) : (
        <table className="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th>Title</th>
              <th>Status</th>
              <th>Impressions</th>
              <th>Conversions</th>
              <th>Conversion Rate</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {popups.map(popup => (
              <tr key={popup.id}>
                <td>
                  <strong>
                    <a href="#" onClick={(e) => { e.preventDefault(); onSelect(popup); }}>
                      {popup.title || 'Untitled Popup'}
                    </a>
                  </strong>
                </td>
                <td>
                  <span className={`status-${popup.status}`}>
                    {popup.status === 'publish' ? 'Published' : 'Draft'}
                  </span>
                </td>
                <td>{popup.impressions || 0}</td>
                <td>{popup.conversions || 0}</td>
                <td>{popup.conversion_rate || 0}%</td>
                <td>
                  <button 
                    className="button" 
                    onClick={() => onSelect(popup)}
                  >
                    Edit
                  </button>
                  <button 
                    className="button button-link-delete" 
                    onClick={() => onDelete(popup.id)}
                  >
                    Delete
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
};

export default PopupList;

