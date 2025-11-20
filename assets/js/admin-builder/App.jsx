import React, { useState, useEffect } from 'react';
import PopupList from './components/PopupList';
import PopupEditor from './components/PopupEditor';

const App = () => {
  const [popups, setPopups] = useState([]);
  const [selectedPopup, setSelectedPopup] = useState(null);
  const [templates, setTemplates] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Get data from localized script
    if (typeof convertlabBuilder !== 'undefined') {
      setPopups(convertlabBuilder.popups || []);
      setTemplates(convertlabBuilder.templates || []);
      setLoading(false);
    }
  }, []);

  const handleCreateNew = () => {
    setSelectedPopup({
      id: null,
      title: '',
      status: 'draft',
      config: {
        design: {
          title: '',
          text: '',
          image: 0,
          background_color: '#ffffff',
          button_text: 'Submit',
          button_color: '#0073aa',
        },
        fields: [
          {
            type: 'email',
            name: 'email',
            label: 'Email',
            required: true,
            placeholder: 'Enter your email',
          },
        ],
        triggers: {
          page_targeting: 'all',
          time_delay: 0,
          scroll_percent: 0,
          show_once: true,
        },
        thank_you: {
          message: 'Thank you for subscribing!',
          redirect: '',
        },
      },
    });
  };

  const handleSelectPopup = (popup) => {
    setSelectedPopup(popup);
  };

  const handleSavePopup = async (popupData) => {
    try {
      const response = await fetch(`${convertlabBuilder.apiUrl}popup/save`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': convertlabBuilder.nonce,
        },
        body: JSON.stringify(popupData),
      });

      const result = await response.json();

      if (result.success) {
        // Refresh popups list
        const updatedPopups = popups.map(p => 
          p.id === result.id ? { ...popupData, id: result.id } : p
        );
        
        if (!popups.find(p => p.id === result.id)) {
          updatedPopups.push({ ...popupData, id: result.id });
        }

        setPopups(updatedPopups);
        setSelectedPopup({ ...popupData, id: result.id });
        alert('Popup saved successfully!');
      } else {
        alert('Error saving popup: ' + (result.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('Error saving popup:', error);
      alert('Error saving popup. Please try again.');
    }
  };

  const handleDeletePopup = async (popupId) => {
    if (!confirm('Are you sure you want to delete this popup?')) {
      return;
    }

    // TODO: Implement delete via REST API
    const updatedPopups = popups.filter(p => p.id !== popupId);
    setPopups(updatedPopups);
    
    if (selectedPopup && selectedPopup.id === popupId) {
      setSelectedPopup(null);
    }
  };

  if (loading) {
    return <div>Loading...</div>;
  }

  return (
    <div className="convertlab-builder">
      {selectedPopup ? (
        <PopupEditor
          popup={selectedPopup}
          templates={templates}
          onSave={handleSavePopup}
          onCancel={() => setSelectedPopup(null)}
        />
      ) : (
        <PopupList
          popups={popups}
          onSelect={handleSelectPopup}
          onCreateNew={handleCreateNew}
          onDelete={handleDeletePopup}
        />
      )}
    </div>
  );
};

export default App;

